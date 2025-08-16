import { Program } from '../models/Program';

export class ProgramsRepository {
    /**
     * Get all available programs
     */
    getAllPrograms(): Program[] {
        return programs;
    }

    /**
     * Get a program by ID
     */
    getProgramById(id: string): Program | undefined {
        return programs.find(program => program.id === id);
    }

    /**
     * Check if a program exists
     */
    programExists(id: string): boolean {
        return programs.some(program => program.id === id);
    }
}

// Import the mock data
import { programs } from '../models/Program';
